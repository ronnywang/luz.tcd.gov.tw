#include "opencv2/highgui/highgui.hpp"
#include "opencv2/imgproc/imgproc.hpp"
#include <iostream>
#include <stdio.h>
#include <stdlib.h>

using namespace cv;
using namespace std;

Mat src; Mat src_gray;
int thresh = 100;
int max_thresh = 255;
RNG rng(12345);


/** @function main */
int main( int argc, char** argv )
{
  /// Load source image and convert it to gray
  src = imread( argv[1], CV_LOAD_IMAGE_UNCHANGED);

  /// Convert image to gray and blur it
  cvtColor( src, src_gray, CV_BGR2GRAY );
  //blur( src_gray, src_gray, Size(3,3) );

  Mat canny_output;
  vector<vector<Point> > contours;
  vector<Vec4i> hierarchy;

  Canny( src_gray, canny_output, thresh, thresh*2, 3 );
  findContours( canny_output, contours, hierarchy, CV_RETR_EXTERNAL, CV_CHAIN_APPROX_NONE, Point(0, 0) );

  Mat drawing = Mat::zeros( canny_output.size(), CV_8UC3 );

  printf("[");
  for( int i = 0; i < contours.size(); i++ ) {
      printf("\n");
      if (i) { printf(","); }
      printf("[");
      Scalar color = Scalar( rng.uniform(0, 255), rng.uniform(0,255), rng.uniform(0,255) );
      drawContours( drawing, contours, i, color, 1, 8, hierarchy, 0, Point() );

      for ( int j = 0; j < contours[i].size(); j ++){
          if (j) { printf(","); }
          printf("[%d,%d]", contours[i][j].x, contours[i][j].y);
      }
      printf("]");
  }
  printf("\n]");
  if (argc > 2) {
      cv::imwrite("opencv.png", drawing); //canny_output);
  }
}
